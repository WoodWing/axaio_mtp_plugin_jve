/**
 * VersionInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class VersionInfo  implements java.io.Serializable {
    private java.lang.String version;

    private java.lang.String user;

    private java.lang.String comment;

    private java.lang.String slugline;

    private java.util.Calendar created;

    private java.lang.String object;

    private com.woodwing.enterprise.interfaces.services.wfl.State state;

    private com.woodwing.enterprise.interfaces.services.wfl.Attachment file;

    public VersionInfo() {
    }

    public VersionInfo(
           java.lang.String version,
           java.lang.String user,
           java.lang.String comment,
           java.lang.String slugline,
           java.util.Calendar created,
           java.lang.String object,
           com.woodwing.enterprise.interfaces.services.wfl.State state,
           com.woodwing.enterprise.interfaces.services.wfl.Attachment file) {
           this.version = version;
           this.user = user;
           this.comment = comment;
           this.slugline = slugline;
           this.created = created;
           this.object = object;
           this.state = state;
           this.file = file;
    }


    /**
     * Gets the version value for this VersionInfo.
     * 
     * @return version
     */
    public java.lang.String getVersion() {
        return version;
    }


    /**
     * Sets the version value for this VersionInfo.
     * 
     * @param version
     */
    public void setVersion(java.lang.String version) {
        this.version = version;
    }


    /**
     * Gets the user value for this VersionInfo.
     * 
     * @return user
     */
    public java.lang.String getUser() {
        return user;
    }


    /**
     * Sets the user value for this VersionInfo.
     * 
     * @param user
     */
    public void setUser(java.lang.String user) {
        this.user = user;
    }


    /**
     * Gets the comment value for this VersionInfo.
     * 
     * @return comment
     */
    public java.lang.String getComment() {
        return comment;
    }


    /**
     * Sets the comment value for this VersionInfo.
     * 
     * @param comment
     */
    public void setComment(java.lang.String comment) {
        this.comment = comment;
    }


    /**
     * Gets the slugline value for this VersionInfo.
     * 
     * @return slugline
     */
    public java.lang.String getSlugline() {
        return slugline;
    }


    /**
     * Sets the slugline value for this VersionInfo.
     * 
     * @param slugline
     */
    public void setSlugline(java.lang.String slugline) {
        this.slugline = slugline;
    }


    /**
     * Gets the created value for this VersionInfo.
     * 
     * @return created
     */
    public java.util.Calendar getCreated() {
        return created;
    }


    /**
     * Sets the created value for this VersionInfo.
     * 
     * @param created
     */
    public void setCreated(java.util.Calendar created) {
        this.created = created;
    }


    /**
     * Gets the object value for this VersionInfo.
     * 
     * @return object
     */
    public java.lang.String getObject() {
        return object;
    }


    /**
     * Sets the object value for this VersionInfo.
     * 
     * @param object
     */
    public void setObject(java.lang.String object) {
        this.object = object;
    }


    /**
     * Gets the state value for this VersionInfo.
     * 
     * @return state
     */
    public com.woodwing.enterprise.interfaces.services.wfl.State getState() {
        return state;
    }


    /**
     * Sets the state value for this VersionInfo.
     * 
     * @param state
     */
    public void setState(com.woodwing.enterprise.interfaces.services.wfl.State state) {
        this.state = state;
    }


    /**
     * Gets the file value for this VersionInfo.
     * 
     * @return file
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Attachment getFile() {
        return file;
    }


    /**
     * Sets the file value for this VersionInfo.
     * 
     * @param file
     */
    public void setFile(com.woodwing.enterprise.interfaces.services.wfl.Attachment file) {
        this.file = file;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof VersionInfo)) return false;
        VersionInfo other = (VersionInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.version==null && other.getVersion()==null) || 
             (this.version!=null &&
              this.version.equals(other.getVersion()))) &&
            ((this.user==null && other.getUser()==null) || 
             (this.user!=null &&
              this.user.equals(other.getUser()))) &&
            ((this.comment==null && other.getComment()==null) || 
             (this.comment!=null &&
              this.comment.equals(other.getComment()))) &&
            ((this.slugline==null && other.getSlugline()==null) || 
             (this.slugline!=null &&
              this.slugline.equals(other.getSlugline()))) &&
            ((this.created==null && other.getCreated()==null) || 
             (this.created!=null &&
              this.created.equals(other.getCreated()))) &&
            ((this.object==null && other.getObject()==null) || 
             (this.object!=null &&
              this.object.equals(other.getObject()))) &&
            ((this.state==null && other.getState()==null) || 
             (this.state!=null &&
              this.state.equals(other.getState()))) &&
            ((this.file==null && other.getFile()==null) || 
             (this.file!=null &&
              this.file.equals(other.getFile())));
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
        if (getVersion() != null) {
            _hashCode += getVersion().hashCode();
        }
        if (getUser() != null) {
            _hashCode += getUser().hashCode();
        }
        if (getComment() != null) {
            _hashCode += getComment().hashCode();
        }
        if (getSlugline() != null) {
            _hashCode += getSlugline().hashCode();
        }
        if (getCreated() != null) {
            _hashCode += getCreated().hashCode();
        }
        if (getObject() != null) {
            _hashCode += getObject().hashCode();
        }
        if (getState() != null) {
            _hashCode += getState().hashCode();
        }
        if (getFile() != null) {
            _hashCode += getFile().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(VersionInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "VersionInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("version");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Version"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("user");
        elemField.setXmlName(new javax.xml.namespace.QName("", "User"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("comment");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Comment"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("slugline");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Slugline"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("created");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Created"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("object");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Object"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("state");
        elemField.setXmlName(new javax.xml.namespace.QName("", "State"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "State"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("file");
        elemField.setXmlName(new javax.xml.namespace.QName("", "File"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Attachment"));
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
