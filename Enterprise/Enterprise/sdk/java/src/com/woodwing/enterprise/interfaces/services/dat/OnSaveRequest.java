/**
 * OnSaveRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class OnSaveRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String datasourceID;

    private com.woodwing.enterprise.interfaces.services.dat.Placement[] placements;

    public OnSaveRequest() {
    }

    public OnSaveRequest(
           java.lang.String ticket,
           java.lang.String datasourceID,
           com.woodwing.enterprise.interfaces.services.dat.Placement[] placements) {
           this.ticket = ticket;
           this.datasourceID = datasourceID;
           this.placements = placements;
    }


    /**
     * Gets the ticket value for this OnSaveRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this OnSaveRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the datasourceID value for this OnSaveRequest.
     * 
     * @return datasourceID
     */
    public java.lang.String getDatasourceID() {
        return datasourceID;
    }


    /**
     * Sets the datasourceID value for this OnSaveRequest.
     * 
     * @param datasourceID
     */
    public void setDatasourceID(java.lang.String datasourceID) {
        this.datasourceID = datasourceID;
    }


    /**
     * Gets the placements value for this OnSaveRequest.
     * 
     * @return placements
     */
    public com.woodwing.enterprise.interfaces.services.dat.Placement[] getPlacements() {
        return placements;
    }


    /**
     * Sets the placements value for this OnSaveRequest.
     * 
     * @param placements
     */
    public void setPlacements(com.woodwing.enterprise.interfaces.services.dat.Placement[] placements) {
        this.placements = placements;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof OnSaveRequest)) return false;
        OnSaveRequest other = (OnSaveRequest) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.datasourceID==null && other.getDatasourceID()==null) || 
             (this.datasourceID!=null &&
              this.datasourceID.equals(other.getDatasourceID()))) &&
            ((this.placements==null && other.getPlacements()==null) || 
             (this.placements!=null &&
              java.util.Arrays.equals(this.placements, other.getPlacements())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getDatasourceID() != null) {
            _hashCode += getDatasourceID().hashCode();
        }
        if (getPlacements() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPlacements());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPlacements(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(OnSaveRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", ">OnSaveRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("datasourceID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DatasourceID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("placements");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Placements"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "Placement"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Placement"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
