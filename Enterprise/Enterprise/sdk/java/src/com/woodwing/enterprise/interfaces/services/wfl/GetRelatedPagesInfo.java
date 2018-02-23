/**
 * GetRelatedPagesInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetRelatedPagesInfo  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String layoutId;

    private org.apache.axis.types.UnsignedInt[] pageSequences;

    public GetRelatedPagesInfo() {
    }

    public GetRelatedPagesInfo(
           java.lang.String ticket,
           java.lang.String layoutId,
           org.apache.axis.types.UnsignedInt[] pageSequences) {
           this.ticket = ticket;
           this.layoutId = layoutId;
           this.pageSequences = pageSequences;
    }


    /**
     * Gets the ticket value for this GetRelatedPagesInfo.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetRelatedPagesInfo.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the layoutId value for this GetRelatedPagesInfo.
     * 
     * @return layoutId
     */
    public java.lang.String getLayoutId() {
        return layoutId;
    }


    /**
     * Sets the layoutId value for this GetRelatedPagesInfo.
     * 
     * @param layoutId
     */
    public void setLayoutId(java.lang.String layoutId) {
        this.layoutId = layoutId;
    }


    /**
     * Gets the pageSequences value for this GetRelatedPagesInfo.
     * 
     * @return pageSequences
     */
    public org.apache.axis.types.UnsignedInt[] getPageSequences() {
        return pageSequences;
    }


    /**
     * Sets the pageSequences value for this GetRelatedPagesInfo.
     * 
     * @param pageSequences
     */
    public void setPageSequences(org.apache.axis.types.UnsignedInt[] pageSequences) {
        this.pageSequences = pageSequences;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetRelatedPagesInfo)) return false;
        GetRelatedPagesInfo other = (GetRelatedPagesInfo) obj;
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
            ((this.layoutId==null && other.getLayoutId()==null) || 
             (this.layoutId!=null &&
              this.layoutId.equals(other.getLayoutId()))) &&
            ((this.pageSequences==null && other.getPageSequences()==null) || 
             (this.pageSequences!=null &&
              java.util.Arrays.equals(this.pageSequences, other.getPageSequences())));
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
        if (getLayoutId() != null) {
            _hashCode += getLayoutId().hashCode();
        }
        if (getPageSequences() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPageSequences());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPageSequences(), i);
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
        new org.apache.axis.description.TypeDesc(GetRelatedPagesInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetRelatedPagesInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layoutId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LayoutId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageSequences");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageSequences"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "UnsignedInt"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "UnsignedInt"));
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
