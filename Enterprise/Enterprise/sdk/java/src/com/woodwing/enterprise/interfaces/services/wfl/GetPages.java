/**
 * GetPages.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetPages  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params;

    private java.lang.String[] IDs;

    private java.lang.String[] pageOrders;

    private java.lang.String[] pageSequences;

    private com.woodwing.enterprise.interfaces.services.wfl.Edition edition;

    private com.woodwing.enterprise.interfaces.services.wfl.RenditionType[] renditions;

    private java.lang.Boolean requestMetaData;

    private java.lang.Boolean requestFiles;

    public GetPages() {
    }

    public GetPages(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params,
           java.lang.String[] IDs,
           java.lang.String[] pageOrders,
           java.lang.String[] pageSequences,
           com.woodwing.enterprise.interfaces.services.wfl.Edition edition,
           com.woodwing.enterprise.interfaces.services.wfl.RenditionType[] renditions,
           java.lang.Boolean requestMetaData,
           java.lang.Boolean requestFiles) {
           this.ticket = ticket;
           this.params = params;
           this.IDs = IDs;
           this.pageOrders = pageOrders;
           this.pageSequences = pageSequences;
           this.edition = edition;
           this.renditions = renditions;
           this.requestMetaData = requestMetaData;
           this.requestFiles = requestFiles;
    }


    /**
     * Gets the ticket value for this GetPages.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetPages.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the params value for this GetPages.
     * 
     * @return params
     */
    public com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] getParams() {
        return params;
    }


    /**
     * Sets the params value for this GetPages.
     * 
     * @param params
     */
    public void setParams(com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params) {
        this.params = params;
    }


    /**
     * Gets the IDs value for this GetPages.
     * 
     * @return IDs
     */
    public java.lang.String[] getIDs() {
        return IDs;
    }


    /**
     * Sets the IDs value for this GetPages.
     * 
     * @param IDs
     */
    public void setIDs(java.lang.String[] IDs) {
        this.IDs = IDs;
    }


    /**
     * Gets the pageOrders value for this GetPages.
     * 
     * @return pageOrders
     */
    public java.lang.String[] getPageOrders() {
        return pageOrders;
    }


    /**
     * Sets the pageOrders value for this GetPages.
     * 
     * @param pageOrders
     */
    public void setPageOrders(java.lang.String[] pageOrders) {
        this.pageOrders = pageOrders;
    }


    /**
     * Gets the pageSequences value for this GetPages.
     * 
     * @return pageSequences
     */
    public java.lang.String[] getPageSequences() {
        return pageSequences;
    }


    /**
     * Sets the pageSequences value for this GetPages.
     * 
     * @param pageSequences
     */
    public void setPageSequences(java.lang.String[] pageSequences) {
        this.pageSequences = pageSequences;
    }


    /**
     * Gets the edition value for this GetPages.
     * 
     * @return edition
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Edition getEdition() {
        return edition;
    }


    /**
     * Sets the edition value for this GetPages.
     * 
     * @param edition
     */
    public void setEdition(com.woodwing.enterprise.interfaces.services.wfl.Edition edition) {
        this.edition = edition;
    }


    /**
     * Gets the renditions value for this GetPages.
     * 
     * @return renditions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RenditionType[] getRenditions() {
        return renditions;
    }


    /**
     * Sets the renditions value for this GetPages.
     * 
     * @param renditions
     */
    public void setRenditions(com.woodwing.enterprise.interfaces.services.wfl.RenditionType[] renditions) {
        this.renditions = renditions;
    }


    /**
     * Gets the requestMetaData value for this GetPages.
     * 
     * @return requestMetaData
     */
    public java.lang.Boolean getRequestMetaData() {
        return requestMetaData;
    }


    /**
     * Sets the requestMetaData value for this GetPages.
     * 
     * @param requestMetaData
     */
    public void setRequestMetaData(java.lang.Boolean requestMetaData) {
        this.requestMetaData = requestMetaData;
    }


    /**
     * Gets the requestFiles value for this GetPages.
     * 
     * @return requestFiles
     */
    public java.lang.Boolean getRequestFiles() {
        return requestFiles;
    }


    /**
     * Sets the requestFiles value for this GetPages.
     * 
     * @param requestFiles
     */
    public void setRequestFiles(java.lang.Boolean requestFiles) {
        this.requestFiles = requestFiles;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetPages)) return false;
        GetPages other = (GetPages) obj;
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
            ((this.params==null && other.getParams()==null) || 
             (this.params!=null &&
              java.util.Arrays.equals(this.params, other.getParams()))) &&
            ((this.IDs==null && other.getIDs()==null) || 
             (this.IDs!=null &&
              java.util.Arrays.equals(this.IDs, other.getIDs()))) &&
            ((this.pageOrders==null && other.getPageOrders()==null) || 
             (this.pageOrders!=null &&
              java.util.Arrays.equals(this.pageOrders, other.getPageOrders()))) &&
            ((this.pageSequences==null && other.getPageSequences()==null) || 
             (this.pageSequences!=null &&
              java.util.Arrays.equals(this.pageSequences, other.getPageSequences()))) &&
            ((this.edition==null && other.getEdition()==null) || 
             (this.edition!=null &&
              this.edition.equals(other.getEdition()))) &&
            ((this.renditions==null && other.getRenditions()==null) || 
             (this.renditions!=null &&
              java.util.Arrays.equals(this.renditions, other.getRenditions()))) &&
            ((this.requestMetaData==null && other.getRequestMetaData()==null) || 
             (this.requestMetaData!=null &&
              this.requestMetaData.equals(other.getRequestMetaData()))) &&
            ((this.requestFiles==null && other.getRequestFiles()==null) || 
             (this.requestFiles!=null &&
              this.requestFiles.equals(other.getRequestFiles())));
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
        if (getParams() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getParams());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getParams(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getIDs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPageOrders() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPageOrders());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPageOrders(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
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
        if (getEdition() != null) {
            _hashCode += getEdition().hashCode();
        }
        if (getRenditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRenditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRenditions(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRequestMetaData() != null) {
            _hashCode += getRequestMetaData().hashCode();
        }
        if (getRequestFiles() != null) {
            _hashCode += getRequestFiles().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetPages.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetPages"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("params");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Params"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "QueryParam"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "QueryParam"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("IDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "IDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageOrders");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageOrders"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageSequences");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageSequences"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("edition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Edition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Edition"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("renditions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Renditions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "RenditionType"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestFiles");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestFiles"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
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
